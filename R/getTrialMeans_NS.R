# Revised March 14, 2018 by Nicholas Santantonio
# this now allows differen tmodels for each trait, based on what data is available
# for example, if it is a RCBD, but one trait was only measured in one rep, it will be treated as a CRD for that trait
defaultContr <- options("contrasts") # get default contrasts setting
options(contrasts = c("contr.sum", "contr.sum")) # change to sum constraints so no fancy calculations needed

# function to get genotype estimates plus trait mean 
getCoef <- function(b, term, termLevels) {
    coefs <- b[grep(term, names(b))]
    coefTail <- -sum(coefs)
    coefs <- c(coefs, coefTail)
    names(coefs) <- termLevels
    return(coefs + b[["(Intercept)"]])
}

# merge estimates with missing
getMissing <- function(eff, allLevels){
    miss <- sapply(allLevels, function(x) NA)
    wMiss <- c(eff, miss[!names(miss) %in% names(eff)])
    wMiss[allLevels]
}

# NOTE: There cannot be spaces in trait names.
plotData <- read.table("plot-pheno.txt", sep=",", header=TRUE, stringsAsFactors=FALSE)
file_out <- "mean-output.txt"

# Analyses to do as a function of the existence of Replication and Block factors:
# Replication Block   Analysis
# NA          NA      CRD model: use simple averages
# !NA         NA      RCBC model: use fixed effects and return LS means
# NA          !NA     no-name model: blocks as random effects with no replication effect
# !NA         !NA     Incomplete block model: Replications fixed and blocks random
dataCols <- names(plotData)[!names(plotData) %in% c("line", "plot", "replication", "block", "subblock", "treatment")]
ghat <- list()
sigma <- NULL
stdErr <- NULL
trialMean <- NULL
trialReps <- NULL

for(trait in dataCols){
    plotDataTrait <- plotData[!is.na(plotData[[trait]]), ]
    hasRep <- all(!is.na(plotDataTrait$replication)) & (length(unique(plotDataTrait$replication)) > 1)
    hasBlk <- all(!is.na(plotDataTrait$block)) & (length(unique(plotDataTrait$block)) > 1)

    dF <- data.frame(line=factor(plotDataTrait$line), rep=factor(plotDataTrait$replication), plotDataTrait[trait])
    if(hasBlk) dF$block <- factor(plotDataTrait$block)

    if (!hasBlk){ # define and fit fixed effects models
        if (!hasRep) {
            model <- paste(trait, "~ line")
            message(paste0("CRD model: use simple averages for trait ", trait))
        } else {
            model <- paste(trait, "~ rep + line")
            message(paste0("RCBD model: use fixed effects and return LS means for trait ", trait));
        }
        fit <- lm(as.formula(model), data = dF) # fit ols model
        fitCoef <- fit$coefficients # extract fixed coefficients
    } else { # define and fit mixed model
        library(lme4)
        if (!hasRep) {
            model <- paste(trait, "~ line + (1 | block)")
            message(paste0("no-name model: blocks as random effects with no replication effect for trait ", trait))
        } else {
            model <- paste(trait, "~ rep + line + (1 | block:rep)")
            message(paste0("Incomplete block model: Replications fixed and blocks random for trait ", trait))
        }
        fit <- lmer(as.formula(model), data = dF) # fit mixed model
        fitCoef <- fixef(fit) # extract fixed coefficients 
    }
    trialMean <- c(trialMean, fitCoef[["(Intercept)"]]) # grand mean
    trialReps <- c(trialReps, round(mean(table(dF$line), 1))) # report average number of reps

    effects <- getCoef(b = fitCoef, term = "line", termLevels = levels(dF[["line"]])) # get genotype estimates + grand mean
    ghat[[trait]] <- getMissing(effects, allLevels = levels(factor(plotData$line))) # report means with missing added
    
    sumFit <- summary(fit) # get model summary
    sigma <- c(sigma, sumFit$sigma) # extract rMSE

    se <- coef(sumFit)[, "Std. Error"] # get standard errors
    avgGse <- mean(se[grep("line", names(se))]) # get average standard error for lines, Not sure how useful this is. We should report individual se
    stdErr <- c(stdErr, avgGse) # report average standard error for lines as stdErr
}

result <- do.call(cbind, ghat)
write.table(result, file=file_out, quote=FALSE)

# I suggest adding the error variance, which is arguably more useful as a summary statistic than the average genotype standard error
# metaData <- data.frame(trialMean = trialMean, stdError = stdErr, replications = trialReps, sigma = sigma)
metaData <- data.frame(trialMean = trialMean, stdError = stdErr, replications = trialReps)
rownames(metaData) <- dataCols
write.table(metaData, file="metaData.txt", quote=FALSE)

options(contrasts = defaultContr[["contrasts"]])
